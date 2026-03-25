import React, {useEffect, useState, useCallback} from 'react';
import {View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Button} from 'react-native';
import {useFocusEffect} from '@react-navigation/native';
import PlayerService from '../../services/PlayerService';
import {Player} from '../../types';

export default function PlayerListScreen({navigation}: any) {
    const [players, setPlayers] = useState<Player[]>([]);
    const [loading, setLoading] = useState(true);

    const loadPlayers = useCallback(() => {
        PlayerService.getPlayers()
            .then(setPlayers)
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        loadPlayers();
    }, [loadPlayers]);

    useFocusEffect(
        useCallback(() => {
            loadPlayers();
        }, [loadPlayers])
    );

    useEffect(() => {
        navigation.setOptions({
            headerRight: () => (
                <View style={{marginRight: 15}}>
                    <Button
                        title="New"
                        onPress={() => navigation.navigate('PlayerForm', {title: 'Create Player'})}
                    />
                </View>
            ),
        });
    }, [navigation]);

    if (loading) {
        return <ActivityIndicator size="large" style={styles.centered}/>;
    }

    return (
        <View style={styles.container}>
            <FlatList
                data={players}
                keyExtractor={(item) => item.id.toString()}
                renderItem={({item}) => (
                    <View style={styles.itemContainer}>
                        <TouchableOpacity
                            style={styles.itemInfo}
                            onPress={() => navigation.navigate('PlayerDetail', {id: item.id})}
                        >
                            <Text style={styles.name}>{item.firstname} {item.lastname}</Text>
                            <Text>Handicap Index: {item.seedHandicapIndex}</Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={styles.editButton}
                            onPress={() => navigation.navigate('PlayerForm', {id: item.id, title: 'Edit Player'})}
                        >
                            <Text style={styles.editButtonText}>Edit</Text>
                        </TouchableOpacity>
                    </View>
                )}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff'},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    itemContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 15,
        borderBottomWidth: 1,
        borderBottomColor: '#eee'
    },
    itemInfo: {flex: 1},
    name: {fontSize: 18, fontWeight: 'bold'},
    editButton: {
        paddingVertical: 5,
        paddingHorizontal: 15,
        backgroundColor: '#007AFF',
        borderRadius: 5,
    },
    editButtonText: {color: '#fff', fontWeight: 'bold'},
});
